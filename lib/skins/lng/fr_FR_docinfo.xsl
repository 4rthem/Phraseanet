<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:param name="docutype" select="'unknown'" />
<xsl:param name="duration" select="'00:00'" />
<xsl:output method="html"/>
	<xsl:decimal-format name="french" decimal-separator="," grouping-separator="" />
	<xsl:decimal-format name="us" decimal-separator="." grouping-separator=" " />
	<xsl:template match="/">   

		Record_id : <xsl:value-of select="$record_id"/><br />
		Largeur : <xsl:value-of select="record/doc/@width"/><br />
		Hauteur: <xsl:value-of select="record/doc/@height"/><br />
		Nom Original : <xsl:value-of select="record/doc/@originalname"/><br />
		Type : <xsl:value-of select="record/doc/@mime"/>
		<xsl:if test="string(number(record/doc/@size)) != 'NaN'">
			<br />Taille : <xsl:value-of select="format-number((record/doc/@size) div (1024*1024), '# ###.##', 'us')"/> Mo
		</xsl:if>
		
				
		<xsl:if test="$docutype = 'image'">
			<xsl:if test="string((record/doc/@width) div (300)*2.54) != 'NaN'">
				<br />
				<br />
				Dimensions à l'impression
				<br/>
				300 dpi : <xsl:value-of select="format-number((record/doc/@width) div (300)*2.54 , '# ###.##', 'us')"/> cm x <xsl:value-of select="format-number((record/doc/@height) div (300)*2.54 , '# ###.##', 'us')"/> cm
				<br/>   72 dpi : <xsl:value-of select="format-number((record/doc/@width) div (72)*2.54 , '# ###.##', 'us')"/> cm x <xsl:value-of select="format-number((record/doc/@height) div (72)*2.54 , '# ###.##', 'us')"/> cm
			</xsl:if>
		</xsl:if>
		<xsl:if test="$docutype = 'video'">
			<br/><br/>
			<xsl:if test="$duration != '00:00'">
				Durée : <xsl:value-of select="$duration"/><br/>
			</xsl:if>
			Images par secondes : <xsl:value-of select="round(record/doc/@frameRate)"/> ips<br/>
			Codec Audio : <xsl:value-of select="record/doc/@audiocodec"/><br/>
			Codec Video : <xsl:value-of select="record/doc/@videocodec"/><br/>
			<xsl:if test="string(round((record/doc/@bitrate) div 1000)) != 'NaN'">
				Debit global : <xsl:value-of select="round((record/doc/@bitrate) div 1000)"/> kbps<br/>
			</xsl:if>
			<xsl:if test="string(round((record/doc/@videobitrate) div 1000)) != 'NaN'">
				Debit video : <xsl:value-of select="round((record/doc/@videobitrate) div 1000)"/> kbps<br/>
			</xsl:if>
			<xsl:if test="string(round((record/doc/@audiobitrate) div 1000)) != 'NaN'">
				Debit audio : <xsl:value-of select="round((record/doc/@audiobitrate) div 1000)"/> kbps<br/>
			</xsl:if>
			<xsl:if test="string(round((record/doc/@audiosamplerate) div 1000)) != 'NaN'">
				Frequence d'echantillonage : <xsl:value-of select="round((record/doc/@audiosamplerate) div 1000)"/> kHz<br/>
			</xsl:if>

		</xsl:if>
		<xsl:if test="$docutype = 'audio'">
			<br/><br/>
			<xsl:if test="$duration != '00:00'">
				Durée : <xsl:value-of select="$duration"/><br/>
			</xsl:if>
			<xsl:if test="string(record/doc/@audiocodec) != ''">
				Codec : <xsl:value-of select="record/doc/@audiocodec"/><br/>
			</xsl:if>
			<xsl:if test="string(round((record/doc/@audiobitrate) div 1000)) != 'NaN'">
				Debit audio : <xsl:value-of select="round((record/doc/@audiobitrate) div 1000)"/> kbps<br/>
			</xsl:if>
			<xsl:if test="string(round((record/doc/@audiosamplerate) div 1000)) != 'NaN'">
				Frequence d'echantillonage : <xsl:value-of select="round((record/doc/@audiosamplerate) div 1000)"/> kHz<br/>
			</xsl:if>

		</xsl:if>
		
		
	</xsl:template>
</xsl:stylesheet>